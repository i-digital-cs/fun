import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/friends/domain/friendship_state.dart';
import 'package:fun_mobile/features/profile/domain/public_profile.dart';

class PublicProfileRemoteSource {
  PublicProfileRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, PublicProfile? data})> show(
    String publicId,
  ) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/users/$publicId',
      );
      final body = response.data!;
      final data = body['data'] as Map<String, dynamic>? ?? body;
      final stats = data['stats'] as Map<String, dynamic>? ?? {};
      final city = data['city'];
      final hosted = (data['hosted_events'] as List<dynamic>? ?? [])
          .whereType<Map>()
          .map((e) {
            final m = Map<String, dynamic>.from(e);
            return PublicHostedEvent(
              id: m['id'] as int? ?? 0,
              title: m['title'] as String? ?? '',
              startsAt: m['starts_at'] is String
                  ? DateTime.tryParse(m['starts_at'] as String)
                  : null,
              city: m['city'] as String?,
              membersCount: m['members_count'] as int?,
              maxParticipants: m['max_participants'] as int?,
            );
          })
          .toList();

      return (
        failure: null,
        data: PublicProfile(
          id: data['id'] as int?,
          publicId: data['public_id'] as String? ?? publicId,
          name: data['name'] as String? ?? '',
          initials: data['initials'] as String? ?? '?',
          profileImagePath: data['profile_image_path'] as String?,
          roleLabel: data['role_label'] as String? ?? '',
          cityName: city is Map ? city['name'] as String? : null,
          hostedEventsCount: stats['hosted_events'] as int? ?? 0,
          joinedEventsCount: stats['joined_events'] as int? ?? 0,
          ratingAverage: (stats['rating_average'] as num?)?.toDouble(),
          ratingCount: stats['rating_count'] as int? ?? 0,
          hostedEvents: hosted,
          friendship: FriendshipState.fromJson(
            data['friendship'] is Map
                ? Map<String, dynamic>.from(data['friendship'] as Map)
                : null,
          ),
          canMessage: data['can_message'] as bool? ?? false,
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }
}
