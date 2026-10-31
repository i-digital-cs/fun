import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/upload_validator.dart';
import 'package:fun_mobile/core/widgets/attachment_folder_picker.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/maps_url_parser.dart';
import 'package:fun_mobile/features/events/domain/place_suggestion.dart';
import 'package:fun_mobile/features/events/presentation/create_event_controller.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_place_map.dart';
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
  String? _sex = 'mix';
  final _requirements = TextEditingController();
  final _expectedCost = TextEditingController();
  final _costPerPerson = TextEditingController();
  final _picker = ImagePicker();

  int get _controllerKey => widget.eventId ?? 0;

  bool get _isEdit => widget.eventId != null;

  CreateEventController get _ctrl =>
      ref.read(createEventControllerProvider(_controllerKey).notifier);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _bootstrap());
  }

  Future<void> _bootstrap() async {
    final result = await _ctrl.bootstrap();
    if (!mounted) return;
    if (result.gate == CreateGate.needIdentity) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).notActiveAccount)),
      );
      context.go('/identity');
      return;
    }
    final event = result.editEvent;
    if (event != null) {
      _title.text = event.title;
      _description.text = event.description ?? '';
      _venue.text = event.venue;
      _mapsUrl.text = event.mapsUrl ?? '';
      _maxParticipants.text = event.maxParticipants.toString();
      _sex = event.sex ?? 'mix';
      _requirements.text = event.requirements ?? '';
      _expectedCost.text = event.expectedCost ?? '';
      _costPerPerson.text = event.costPerPerson?.toString() ?? '';
    }
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

  String? _placeStatusLabel(CreatePlaceStatus status, AppLocalizations l10n) {
    return switch (status) {
      CreatePlaceStatus.none => null,
      CreatePlaceStatus.pinned => l10n.placePinned,
      CreatePlaceStatus.fromLink => l10n.placeFromLink,
      CreatePlaceStatus.fromSuggest => l10n.placeFromSuggest,
      CreatePlaceStatus.resolving => l10n.placeResolving,
      CreatePlaceStatus.unresolved => l10n.mapsUrlUnresolved,
      CreatePlaceStatus.cleared => l10n.placeCleared,
    };
  }

  Future<void> _pickDateTime({required bool isStart}) async {
    final state = ref.read(createEventControllerProvider(_controllerKey));
    final now = DateTime.now();
    final initial = isStart
        ? (state.startsAt ?? now)
        : (state.endsAt ?? state.startsAt ?? now);
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
    final value = DateTime(
      date.year,
      date.month,
      date.day,
      time.hour,
      time.minute,
    );
    if (isStart) {
      _ctrl.setStartsAt(value);
    } else {
      _ctrl.setEndsAt(value);
    }
  }

  Future<void> _pickImage() async {
    final file = await _picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 2000,
      imageQuality: 85,
    );
    if (file == null || !mounted) return;
    final validation = await UploadValidator.validateImageFile(file.path);
    if (!mounted) return;
    if (!validation.ok) {
      final l10n = AppLocalizations.of(context);
      final msg = switch (validation.errorKey) {
        'uploadTooLarge' => l10n.uploadTooLarge,
        'uploadBadType' => l10n.uploadBadType,
        _ => l10n.uploadMissing,
      };
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
      return;
    }
    _ctrl.setImagePath(file.path);
  }

  Future<void> _applyMapsUrl() async {
    await _ctrl.applyMapsUrl(_mapsUrl.text);
    if (!mounted) return;
    final resolved = ref
        .read(createEventControllerProvider(_controllerKey))
        .resolvedMapsUrl;
    if (resolved != null && resolved.isNotEmpty) {
      _mapsUrl.text = resolved;
    }
  }

  void _clearPlace() {
    _ctrl.clearPlace();
    _mapsUrl.clear();
  }

  void _onMapPin(double lat, double lng) {
    final next = _ctrl.pin(lat, lng, currentMapsUrl: _mapsUrl.text);
    if (next != null) _mapsUrl.text = next;
  }

  EventDraft? _buildDraft(CreateEventState state) {
    if (state.startsAt == null ||
        state.categoryId == null ||
        state.cityId == null) {
      return null;
    }
    final maxP = int.tryParse(_maxParticipants.text.trim());
    if (maxP == null) return null;
    final sex = _sex;
    if (sex == null || sex.isEmpty) return null;
    // Pin coords are authoritative — keep maps_url in sync so "Open in maps"
    // matches the map (pasted Google links must not win over a moved pin).
    final lat = state.latitude;
    final lng = state.longitude;
    final mapsUrl = (lat != null && lng != null)
        ? MapsUrlParser.openStreetMapUrl(lat, lng)
        : _mapsUrl.text.trim();
    return EventDraft(
      title: _title.text.trim(),
      description: _description.text.trim().isEmpty
          ? null
          : _description.text.trim(),
      categoryId: state.categoryId!,
      cityId: state.cityId!,
      venue: _venue.text.trim(),
      startsAt: state.startsAt!,
      endsAt: state.endsAt,
      maxParticipants: maxP,
      sex: sex,
      requirements: _requirements.text.trim().isEmpty
          ? null
          : _requirements.text.trim(),
      expectedCost: _expectedCost.text.trim().isEmpty
          ? null
          : _expectedCost.text.trim(),
      costPerPerson: num.tryParse(_costPerPerson.text.trim()),
      imagePath: state.imagePath,
      latitude: lat,
      longitude: lng,
      mapsUrl: mapsUrl.isEmpty ? null : mapsUrl,
    );
  }

  Future<void> _submit({required bool publishIntent}) async {
    final state = ref.read(createEventControllerProvider(_controllerKey));
    if (state.submitting) return;
    if (!_formKey.currentState!.validate()) return;
    if (state.startsAt == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(AppLocalizations.of(context).validationRequired),
        ),
      );
      return;
    }
    final built = _buildDraft(state);
    if (built == null) return;
    final draft = built.copyWith(publishIntent: publishIntent);

    final result = await _ctrl.submit(draft);
    if (!mounted) return;
    final l10n = AppLocalizations.of(context);

    // Status mismatch after HTTP success — never claim published/draft success.
    if (result.outcome == CreateSubmitOutcome.unexpectedDraft ||
        result.outcome == CreateSubmitOutcome.unexpectedPublished) {
      final event = result.event;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.publishStatusMismatch)),
      );
      if (event != null) {
        context.go('/my-events');
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (!mounted) return;
          // Authorized edit — avoid public show that may 403 for drafts.
          context.push('/events/${event.id}/edit');
        });
      }
      return;
    }

    if (!result.ok) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.error ?? l10n.errorGeneric)),
      );
      return;
    }

    final event = result.event!;
    if (_isEdit) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.eventSaved)),
      );
      if (!mounted) return;
      context.go('/my-events');
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        context.push('/events/${event.id}');
      });
      return;
    }

    if (result.isPublished) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.eventPublished),
          action: SnackBarAction(
            label: l10n.viewEvent,
            onPressed: () {
              if (!mounted) return;
              context.push('/events/${event.id}');
            },
          ),
        ),
      );
      // NAV-F3: shell then detail so Back is not an orphan root route.
      context.go('/my-events');
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        context.push('/events/${event.id}');
      });
      return;
    }

    // Draft saved — continue editing; do not open public show.
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(l10n.eventSaved),
        action: SnackBarAction(
          label: l10n.continueEditing,
          onPressed: () {
            if (!mounted) return;
            context.push('/events/${event.id}/edit');
          },
        ),
      ),
    );
    context.go('/my-events');
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      context.push('/events/${event.id}/edit');
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final state = ref.watch(createEventControllerProvider(_controllerKey));

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(_isEdit ? l10n.editEvent : l10n.createEvent)),
      body: AsyncBody(
        isLoading: state.isLoading,
        error: state.error,
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
                sex: _sex,
                onSexChanged: (v) => setState(() => _sex = v),
                requirementsController: _requirements,
                expectedCostController: _expectedCost,
                costPerPersonController: _costPerPerson,
                categories: state.categories,
                cities: state.cities,
                categoryId: state.categoryId,
                cityId: state.cityId,
                startsAt: state.startsAt,
                endsAt: state.endsAt,
                imagePath: state.imagePath,
                locale: locale,
                latitude: state.latitude,
                longitude: state.longitude,
                placeStatus: _placeStatusLabel(state.placeStatus, l10n),
                onCategoryChanged: _ctrl.setCategoryId,
                onCityChanged: _ctrl.setCityId,
                onPickStart: () => _pickDateTime(isStart: true),
                onPickEnd: () => _pickDateTime(isStart: false),
                onPickImage: _pickImage,
                onClearImage: state.imagePath == null ? null : _ctrl.clearImage,
                onMapsUrlChanged: _applyMapsUrl,
                onClearPlace: _clearPlace,
                onPinChanged: _onMapPin,
                onSuggestVenue: (q) => _ctrl.suggestPlaces(q),
                onVenueSuggestionSelected: (s) {
                  _venue.text = s.label;
                  final next = _ctrl.applySuggestion(
                    label: s.label,
                    lat: s.latitude,
                    lng: s.longitude,
                  );
                  _mapsUrl.text = next;
                },
              ),
              const SizedBox(height: 24),
              if (_isEdit)
                GradientButton(
                  label: l10n.save,
                  isLoading: state.submitting,
                  onPressed: state.submitting
                      ? null
                      : () => _submit(publishIntent: false),
                )
              else ...[
                GradientButton(
                  label: l10n.createEvent,
                  isLoading: state.submitting,
                  onPressed: state.submitting
                      ? null
                      : () => _submit(publishIntent: true),
                ),
                const SizedBox(height: 12),
                OutlinedButton(
                  onPressed: state.submitting
                      ? null
                      : () => _submit(publishIntent: false),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    side: const BorderSide(color: AppColors.primary),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: Text(l10n.createDraft),
                ),
              ],
              SizedBox(height: MediaQuery.paddingOf(context).bottom + 8),
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
    required this.sex,
    required this.onSexChanged,
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
    required this.onPinChanged,
    required this.onSuggestVenue,
    required this.onVenueSuggestionSelected,
    this.onClearImage,
    this.latitude,
    this.longitude,
    this.placeStatus,
  });

  final TextEditingController titleController;
  final TextEditingController descriptionController;
  final TextEditingController venueController;
  final TextEditingController mapsUrlController;
  final TextEditingController maxParticipantsController;
  final String? sex;
  final ValueChanged<String?> onSexChanged;
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
  final VoidCallback? onClearImage;
  final VoidCallback onMapsUrlChanged;
  final VoidCallback onClearPlace;
  final void Function(double lat, double lng) onPinChanged;
  final Future<List<PlaceSuggestion>> Function(String query) onSuggestVenue;
  final ValueChanged<PlaceSuggestion> onVenueSuggestionSelected;
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
          _VenueSuggestField(
            controller: venueController,
            onSuggest: onSuggestVenue,
            onSelected: onVenueSuggestionSelected,
          ),
          const SizedBox(height: 4),
          Text(
            l10n.venueSuggestHelp,
            style: const TextStyle(color: AppColors.muted, fontSize: 12),
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
          const SizedBox(height: 10),
          EventPlaceMapPicker(
            latitude: latitude,
            longitude: longitude,
            onChanged: onPinChanged,
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
            value: startsAt != null
                ? dateFmt.format(startsAt!.toLocal())
                : null,
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
              if (v == null || v.trim().isEmpty) {
                return l10n.validationRequired;
              }
              if (int.tryParse(v.trim()) == null) {
                return l10n.validationRequired;
              }
              return null;
            },
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            key: ValueKey('event-sex-$sex'),
            initialValue: sex,
            decoration: InputDecoration(
              labelText: l10n.eventSex,
              helperText: l10n.eventSexHelp,
            ),
            items: [
              DropdownMenuItem(value: 'male', child: Text(l10n.sexMale)),
              DropdownMenuItem(value: 'female', child: Text(l10n.sexFemale)),
              DropdownMenuItem(value: 'mix', child: Text(l10n.sexMix)),
            ],
            onChanged: onSexChanged,
            validator: (v) =>
                (v == null || v.isEmpty) ? l10n.eventSexHelp : null,
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
          AttachmentFolderPicker(
            label: l10n.pickImage,
            path: imagePath,
            onPick: onPickImage,
            onClear: onClearImage,
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

class _VenueSuggestField extends StatefulWidget {
  const _VenueSuggestField({
    required this.controller,
    required this.onSuggest,
    required this.onSelected,
  });

  final TextEditingController controller;
  final Future<List<PlaceSuggestion>> Function(String query) onSuggest;
  final ValueChanged<PlaceSuggestion> onSelected;

  @override
  State<_VenueSuggestField> createState() => _VenueSuggestFieldState();
}

class _VenueSuggestFieldState extends State<_VenueSuggestField> {
  final _focus = FocusNode();
  int _requestId = 0;

  @override
  void dispose() {
    _focus.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return RawAutocomplete<PlaceSuggestion>(
      textEditingController: widget.controller,
      focusNode: _focus,
      optionsBuilder: (textEditingValue) async {
        final q = textEditingValue.text.trim();
        if (q.length < 2) return const Iterable<PlaceSuggestion>.empty();
        final id = ++_requestId;
        final results = await widget.onSuggest(q);
        if (id != _requestId) {
          return const Iterable<PlaceSuggestion>.empty();
        }
        return results;
      },
      displayStringForOption: (option) => option.label,
      onSelected: widget.onSelected,
      fieldViewBuilder: (context, textController, focusNode, onFieldSubmitted) {
        return TextFormField(
          controller: textController,
          focusNode: focusNode,
          decoration: InputDecoration(
            labelText: l10n.venue,
            hintText: l10n.venueHint,
          ),
        );
      },
      optionsViewBuilder: (context, onSelected, options) {
        return Align(
          alignment: AlignmentDirectional.topStart,
          child: Material(
            elevation: 4,
            borderRadius: BorderRadius.circular(12),
            color: AppColors.surface,
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 240, maxWidth: 480),
              child: ListView.builder(
                padding: EdgeInsets.zero,
                shrinkWrap: true,
                itemCount: options.length,
                itemBuilder: (context, index) {
                  final option = options.elementAt(index);
                  return ListTile(
                    dense: true,
                    title: Text(option.label, maxLines: 2),
                    leading: const Icon(Icons.place_outlined, size: 20),
                    onTap: () => onSelected(option),
                  );
                },
              ),
            ),
          ),
        );
      },
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
