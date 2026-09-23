import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/maps_url_parser.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';

class CreateEventPage extends ConsumerStatefulWidget {
  const CreateEventPage({super.key, this.eventId});

  final int? eventId;

  @override
  ConsumerState<CreateEventPage> createState() => _CreateEventPageState();
}

class _CreateEventPageState extends ConsumerState<CreateEventPage> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _venue = TextEditingController();
  final _mapsUrl = TextEditingController();
  final _maxParticipants = TextEditingController(text: '10');
  final _requirements = TextEditingController();
  final _expectedCost = TextEditingController();
  final _costPerPerson = TextEditingController();
  final _picker = ImagePicker();

  List<CategoryItem> _categories = [];
  List<CityItem> _cities = [];
  int? _categoryId;
  int? _cityId;
  DateTime? _startsAt;
  DateTime? _endsAt;
  String? _imagePath;
  String? _error;
  bool _loadingCatalog = true;
  bool _loadingEvent = false;
  bool _submitting = false;
  double? _latitude;
  double? _longitude;
  String? _placeStatus;

  bool get _isEdit => widget.eventId != null;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _bootstrap());
  }

  Future<void> _bootstrap() async {
    final auth = ref.read(authControllerProvider);
    if (auth is Authenticated && !auth.user.canParticipate) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).notActiveAccount)),
      );
      context.go('/identity');
      return;
    }
    await _loadCatalog();
    if (_isEdit) await _loadEvent();
  }

  Future<void> _loadCatalog() async {
    setState(() {
      _loadingCatalog = true;
      _error = null;
    });
    final cats = await ref.read(catalogRepositoryProvider).categories();
    final cities = await ref.read(catalogRepositoryProvider).cities();
    if (!mounted) return;
    if (cats.failure != null || cities.failure != null) {
      setState(() {
        _loadingCatalog = false;
        _error = cats.failure?.message ?? cities.failure?.message;
      });
      return;
    }
    setState(() {
      _loadingCatalog = false;
      _categories = cats.data ?? [];
      _cities = cities.data ?? [];
      _categoryId ??= _categories.firstOrNull?.id;
      _cityId ??= _cities.firstOrNull?.id;
    });
  }

  Future<void> _loadEvent() async {
    setState(() => _loadingEvent = true);
    final result =
        await ref.read(eventsRepositoryProvider).show(widget.eventId!);
    if (!mounted) return;
    setState(() => _loadingEvent = false);
    if (result.failure != null || result.data == null) {
      setState(() => _error = result.failure?.message);
      return;
    }
    final event = result.data!.event;
    _title.text = event.title;
    _description.text = event.description ?? '';
    _venue.text = event.venue;
    _mapsUrl.text = event.mapsUrl ?? '';
    _maxParticipants.text = event.maxParticipants.toString();
    _requirements.text = event.requirements ?? '';
    _expectedCost.text = event.expectedCost ?? '';
    _costPerPerson.text = event.costPerPerson?.toString() ?? '';
    setState(() {
      _categoryId = event.categoryId ?? event.category?.id;
      _cityId = event.cityId ?? event.city?.id;
      _startsAt = event.startsAt;
      _endsAt = event.endsAt;
      _latitude = event.latitude;
      _longitude = event.longitude;
      _placeStatus = event.hasPlacePin ? AppLocalizations.of(context).placePinned : null;
    });
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _venue.dispose();
    _mapsUrl.dispose();
    _maxParticipants.dispose();
    _requirements.dispose();
    _expectedCost.dispose();
    _costPerPerson.dispose();
    super.dispose();
  }

  Future<void> _pickDateTime({required bool isStart}) async {
    final now = DateTime.now();
    final initial = isStart ? (_startsAt ?? now) : (_endsAt ?? _startsAt ?? now);
    final date = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: now.subtract(const Duration(days: 1)),
      lastDate: now.add(const Duration(days: 365)),
    );
    if (date == null || !mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(initial),
    );
    if (time == null || !mounted) return;
    final value = DateTime(date.year, date.month, date.day, time.hour, time.minute);
    setState(() {
      if (isStart) {
        _startsAt = value;
      } else {
        _endsAt = value;
      }
    });
  }

  Future<void> _pickImage() async {
    final file = await _picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 2000,
      imageQuality: 85,
    );
    if (file != null) setState(() => _imagePath = file.path);
  }

  Future<void> _applyMapsUrl() async {
    final raw = _mapsUrl.text.trim();
    final l10n = AppLocalizations.of(context);
    final parsed = MapsUrlParser.parse(raw);
    if (parsed != null) {
      setState(() {
        _latitude = parsed.lat;
        _longitude = parsed.lng;
        _placeStatus = l10n.placeFromLink;
      });
      return;
    }
    if (raw.isEmpty) return;
    setState(() => _placeStatus = l10n.placeResolving);
    final result =
        await ref.read(eventsRepositoryProvider).resolveMapsUrl(raw);
    if (!mounted) return;
    if (result.failure != null || result.data == null) {
      setState(() => _placeStatus = l10n.mapsUrlUnresolved);
      return;
    }
    setState(() {
      _latitude = result.data!.lat;
      _longitude = result.data!.lng;
      _mapsUrl.text = result.data!.mapsUrl;
      _placeStatus = l10n.placeFromLink;
    });
  }

  void _clearPlace() {
    setState(() {
      _latitude = null;
      _longitude = null;
      _mapsUrl.clear();
      _placeStatus = AppLocalizations.of(context).placeCleared;
    });
  }

  EventDraft? _buildDraft() {
    if (_startsAt == null || _categoryId == null || _cityId == null) return null;
    final maxP = int.tryParse(_maxParticipants.text.trim());
    if (maxP == null) return null;
    final mapsUrl = _mapsUrl.text.trim();
    return EventDraft(
      title: _title.text.trim(),
      description: _description.text.trim().isEmpty
          ? null
          : _description.text.trim(),
      categoryId: _categoryId!,
      cityId: _cityId!,
      venue: _venue.text.trim().isEmpty ? null : _venue.text.trim(),
      startsAt: _startsAt!,
      endsAt: _endsAt,
      maxParticipants: maxP,
      requirements: _requirements.text.trim().isEmpty
          ? null
          : _requirements.text.trim(),
      expectedCost: _expectedCost.text.trim().isEmpty
          ? null
          : _expectedCost.text.trim(),
      costPerPerson: num.tryParse(_costPerPerson.text.trim()),
      imagePath: _imagePath,
      latitude: _latitude,
      longitude: _longitude,
      mapsUrl: mapsUrl.isEmpty ? null : mapsUrl,
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_startsAt == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).validationRequired)),
      );
      return;
    }
    final draft = _buildDraft();
    if (draft == null) return;

    setState(() => _submitting = true);
    final repo = ref.read(eventsRepositoryProvider);
    final result = _isEdit
        ? await repo.update(widget.eventId!, draft)
        : await repo.create(draft);
    if (!mounted) return;
    setState(() => _submitting = false);

    if (result.failure != null || result.data == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure?.message ?? 'Error')),
      );
      return;
    }

    final event = result.data!;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.of(context).eventSaved)),
    );

    if (!_isEdit && event.isDraft) {
      final publish = await showDialog<bool>(
        context: context,
        builder: (ctx) => PublishOfferDialog(eventTitle: event.title),
      );
      if (publish == true && mounted) {
        final pub = await repo.publish(event.id);
        if (!mounted) return;
        if (pub.failure != null) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(pub.failure!.message)),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(AppLocalizations.of(context).eventPublished),
            ),
          );
        }
      }
    }

    if (mounted) context.go('/events/${event.id}');
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final loading = _loadingCatalog || _loadingEvent;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Text(_isEdit ? l10n.editEvent : l10n.createEvent),
      ),
      body: AsyncBody(
        isLoading: loading,
        error: _error,
        onRetry: _bootstrap,
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              CreateEventFormCard(
                titleController: _title,
                descriptionController: _description,
                venueController: _venue,
                mapsUrlController: _mapsUrl,
                maxParticipantsController: _maxParticipants,
                requirementsController: _requirements,
                expectedCostController: _expectedCost,
                costPerPersonController: _costPerPerson,
                categories: _categories,
                cities: _cities,
                categoryId: _categoryId,
                cityId: _cityId,
                startsAt: _startsAt,
                endsAt: _endsAt,
                imagePath: _imagePath,
                locale: locale,
                latitude: _latitude,
                longitude: _longitude,
                placeStatus: _placeStatus,
                onCategoryChanged: (v) => setState(() => _categoryId = v),
                onCityChanged: (v) => setState(() => _cityId = v),
                onPickStart: () => _pickDateTime(isStart: true),
                onPickEnd: () => _pickDateTime(isStart: false),
                onPickImage: _pickImage,
                onMapsUrlChanged: _applyMapsUrl,
                onClearPlace: _clearPlace,
              ),
              const SizedBox(height: 24),
              GradientButton(
                label: _isEdit ? l10n.save : l10n.createDraft,
                isLoading: _submitting,
                onPressed: _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class CreateEventFormCard extends StatelessWidget {
  const CreateEventFormCard({
    super.key,
    required this.titleController,
    required this.descriptionController,
    required this.venueController,
    required this.mapsUrlController,
    required this.maxParticipantsController,
    required this.requirementsController,
    required this.expectedCostController,
    required this.costPerPersonController,
    required this.categories,
    required this.cities,
    required this.categoryId,
    required this.cityId,
    required this.startsAt,
    required this.endsAt,
    required this.imagePath,
    required this.locale,
    required this.onCategoryChanged,
    required this.onCityChanged,
    required this.onPickStart,
    required this.onPickEnd,
    required this.onPickImage,
    required this.onMapsUrlChanged,
    required this.onClearPlace,
    this.latitude,
    this.longitude,
    this.placeStatus,
  });

  final TextEditingController titleController;
  final TextEditingController descriptionController;
  final TextEditingController venueController;
  final TextEditingController mapsUrlController;
  final TextEditingController maxParticipantsController;
  final TextEditingController requirementsController;
  final TextEditingController expectedCostController;
  final TextEditingController costPerPersonController;
  final List<CategoryItem> categories;
  final List<CityItem> cities;
  final int? categoryId;
  final int? cityId;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final String? imagePath;
  final String locale;
  final ValueChanged<int?> onCategoryChanged;
  final ValueChanged<int?> onCityChanged;
  final VoidCallback onPickStart;
  final VoidCallback onPickEnd;
  final VoidCallback onPickImage;
  final VoidCallback onMapsUrlChanged;
  final VoidCallback onClearPlace;
  final double? latitude;
  final double? longitude;
  final String? placeStatus;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final dateFmt = DateFormat('MMM d, y · h:mm a', locale);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.border.withValues(alpha: 0.7)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextFormField(
            controller: titleController,
            decoration: InputDecoration(labelText: l10n.eventTitle),
            validator: (v) =>
                v == null || v.trim().isEmpty ? l10n.validationRequired : null,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: descriptionController,
            maxLines: 3,
            decoration: InputDecoration(labelText: l10n.description),
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<int>(
            key: ValueKey('category-$categoryId'),
            initialValue: categoryId,
            decoration: InputDecoration(labelText: l10n.category),
            items: categories
                .map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))
                .toList(),
            onChanged: onCategoryChanged,
            validator: (v) => v == null ? l10n.validationRequired : null,
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<int>(
            key: ValueKey('city-$cityId'),
            initialValue: cityId,
            decoration: InputDecoration(labelText: l10n.city),
            items: cities
                .map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))
                .toList(),
            onChanged: onCityChanged,
            validator: (v) => v == null ? l10n.validationRequired : null,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: venueController,
            decoration: InputDecoration(labelText: l10n.venue),
          ),
          const SizedBox(height: 12),
          Text(
            l10n.placeOnMap,
            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
          ),
          const SizedBox(height: 4),
          Text(
            l10n.placeOnMapHelp,
            style: const TextStyle(color: AppColors.muted, fontSize: 12),
          ),
          const SizedBox(height: 8),
          TextFormField(
            controller: mapsUrlController,
            decoration: InputDecoration(
              labelText: l10n.mapsUrl,
              hintText: l10n.mapsUrlHint,
              suffixIcon: IconButton(
                tooltip: l10n.applyMapsLink,
                onPressed: onMapsUrlChanged,
                icon: const Icon(Icons.link),
              ),
            ),
            onEditingComplete: onMapsUrlChanged,
          ),
          if (latitude != null && longitude != null) ...[
            const SizedBox(height: 8),
            Text(
              '${latitude!.toStringAsFixed(5)}, ${longitude!.toStringAsFixed(5)}',
              style: const TextStyle(
                color: AppColors.primary,
                fontWeight: FontWeight.w600,
                fontSize: 12,
              ),
            ),
          ],
          if (placeStatus != null && placeStatus!.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              placeStatus!,
              style: const TextStyle(color: AppColors.muted, fontSize: 12),
            ),
          ],
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: TextButton(
              onPressed: onClearPlace,
              child: Text(l10n.placeClear),
            ),
          ),
          const SizedBox(height: 12),
          DateTimePickerField(
            label: l10n.startsAt,
            value: startsAt != null ? dateFmt.format(startsAt!.toLocal()) : null,
            onTap: onPickStart,
          ),
          const SizedBox(height: 12),
          DateTimePickerField(
            label: l10n.endsAt,
            value: endsAt != null ? dateFmt.format(endsAt!.toLocal()) : null,
            onTap: onPickEnd,
            optional: true,
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: maxParticipantsController,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(labelText: l10n.maxParticipants),
            validator: (v) {
              if (v == null || v.trim().isEmpty) return l10n.validationRequired;
              if (int.tryParse(v.trim()) == null) return l10n.validationRequired;
              return null;
            },
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: requirementsController,
            maxLines: 2,
            decoration: InputDecoration(labelText: l10n.requirements),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: expectedCostController,
            decoration: InputDecoration(labelText: l10n.expectedCost),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: costPerPersonController,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(labelText: l10n.costPerPerson),
          ),
          const SizedBox(height: 12),
          ImagePickerField(
            path: imagePath,
            onPick: onPickImage,
          ),
        ],
      ),
    );
  }
}

class DateTimePickerField extends StatelessWidget {
  const DateTimePickerField({
    super.key,
    required this.label,
    required this.value,
    required this.onTap,
    this.optional = false,
  });

  final String label;
  final String? value;
  final VoidCallback onTap;
  final bool optional;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: optional ? '$label (${l10n.optional})' : label,
          suffixIcon: const Icon(Icons.calendar_today_outlined),
        ),
        child: Text(
          value ?? l10n.pickDateTime,
          style: TextStyle(
            color: value != null ? AppColors.text : AppColors.muted,
          ),
        ),
      ),
    );
  }
}

class ImagePickerField extends StatelessWidget {
  const ImagePickerField({
    super.key,
    required this.path,
    required this.onPick,
  });

  final String? path;
  final VoidCallback onPick;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return OutlinedButton.icon(
      onPressed: onPick,
      icon: Icon(path != null ? Icons.check_circle : Icons.image_outlined),
      label: Text(path != null ? l10n.imageSelected : l10n.pickImage),
      style: OutlinedButton.styleFrom(
        padding: const EdgeInsets.symmetric(vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }
}

class PublishOfferDialog extends StatelessWidget {
  const PublishOfferDialog({super.key, required this.eventTitle});

  final String eventTitle;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return AlertDialog(
      title: Text(l10n.publishEvent),
      content: Text(l10n.publishOfferBody(eventTitle)),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context, false),
          child: Text(l10n.later),
        ),
        TextButton(
          onPressed: () => Navigator.pop(context, true),
          child: Text(l10n.publish),
        ),
      ],
    );
  }
}
