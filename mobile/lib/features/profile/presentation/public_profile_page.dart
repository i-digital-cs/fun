import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/profile/data/public_profile_remote_source.dart';
import 'package:fun_mobile/features/profile/domain/public_profile.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

final publicProfileRemoteSourceProvider = Provider(
  (ref) => PublicProfileRemoteSource(ref.watch(apiClientProvider)),
);

class PublicProfilePage extends ConsumerStatefulWidget {
  const PublicProfilePage({super.key, required this.publicId});

  final String publicId;

  @override
  ConsumerState<PublicProfilePage> createState() => _PublicProfilePageState();
}

class _PublicProfilePageState extends ConsumerState<PublicProfilePage> {
  PublicProfile? _profile;
  String? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result =
        await ref.read(publicProfileRemoteSourceProvider).show(widget.publicId);
    if (!mounted) return;
    setState(() {
      _loading = false;
      if (result.failure != null) {
        _error = result.failure!.message;
        _profile = null;
      } else {
        _profile = result.data;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.publicProfile)),
      body: _loading
          ? const Center(child: FunLoader())
          : _error != null
              ? Center(child: Text(_error!))
                  : _profile == null
                  ? Center(child: Text(l10n.profileNotFound))
                  : ListView(
                      padding: const EdgeInsets.all(20),
                      children: [
                        PublicProfileCard(profile: _profile!),
                        if (_profile!.hostedEvents.isNotEmpty) ...[
                          const SizedBox(height: 24),
                          Text(
                            l10n.hostedEvents,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(height: 12),
                          ..._profile!.hostedEvents.map((event) {
                            final locale =
                                Localizations.localeOf(context).toString();
                            final when = event.startsAt != null
                                ? DateFormat('MMM d · h:mm a', locale)
                                    .format(event.startsAt!.toLocal())
                                : '';
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 10),
                              child: ListTile(
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                  side: const BorderSide(
                                    color: AppColors.borderSoft,
                                  ),
                                ),
                                tileColor: AppColors.surface,
                                title: Text(
                                  event.title,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                subtitle: Text(
                                  [
                                    if (when.isNotEmpty) when,
                                    if (event.city != null) event.city,
                                  ].join(' · '),
                                ),
                                onTap: () =>
                                    context.push('/events/${event.id}'),
                              ),
                            );
                          }),
                        ],
                      ],
                    ),
    );
  }
}

class PublicProfileCard extends StatelessWidget {
  const PublicProfileCard({super.key, required this.profile});

  final PublicProfile profile;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1E1B4B), Color(0xFF312E81), Color(0xFF4338CA)],
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.28),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 96,
                height: 96,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(8),
                  gradient: AppColors.indigoGradient,
                  image: profile.profileImagePath != null
                      ? DecorationImage(
                          image: NetworkImage(profile.profileImagePath!),
                          fit: BoxFit.cover,
                        )
                      : null,
                ),
                alignment: Alignment.center,
                child: profile.profileImagePath == null
                    ? Text(
                        profile.initials,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 28,
                        ),
                      )
                    : null,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: SizedBox(
                  height: 96,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            profile.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            [
                              profile.roleLabel,
                              if (profile.cityName != null) profile.cityName!,
                            ].join(' · '),
                            style: const TextStyle(
                              color: Color(0xFFC7D2FE),
                              fontSize: 12,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            profile.publicId,
                            style: const TextStyle(
                              color: Color(0xFFFDBA74),
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 6,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Row(
                          children: [
                            _Stat(
                              label: l10n.statHosted,
                              value: '${profile.hostedEventsCount}',
                            ),
                            _Stat(
                              label: l10n.statJoined,
                              value: '${profile.joinedEventsCount}',
                            ),
                            _Stat(
                              label: l10n.statRating,
                              value: profile.ratingCount > 0
                                  ? '${profile.ratingAverage}★'
                                  : '—',
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 10, color: AppColors.muted),
          ),
          Text(
            value,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: AppColors.primary,
            ),
          ),
        ],
      ),
    );
  }
}
