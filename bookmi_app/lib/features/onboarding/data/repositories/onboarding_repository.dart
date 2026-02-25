import 'package:bookmi_app/core/network/api_client.dart';
import 'package:bookmi_app/features/onboarding/data/models/onboarding_status_model.dart';
import 'package:dio/dio.dart';

class OnboardingRepository {
  OnboardingRepository({required ApiClient apiClient}) : _dio = apiClient.dio;

  OnboardingRepository.forTesting({required Dio dio}) : _dio = dio;

  final Dio _dio;

  Future<OnboardingStatusModel> getOnboardingStatus() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        '/api/v1/talent_profiles/me',
      );
      return OnboardingStatusModel.fromJson(
        response.data ?? {},
      );
    } on DioException catch (e) {
      throw Exception(
        e.response?.data?['error']?['message'] ??
            'Erreur de chargement du statut.',
      );
    }
  }
}
