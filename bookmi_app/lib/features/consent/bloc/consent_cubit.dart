import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/consent/bloc/consent_state.dart';
import 'package:bookmi_app/features/consent/data/repositories/consent_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class ConsentCubit extends Cubit<ConsentState> {
  ConsentCubit({required ConsentRepository repository})
    : _repository = repository,
      super(const ConsentInitial());

  final ConsentRepository _repository;

  Future<void> fetchConsents() async {
    emit(const ConsentLoading());
    final result = await _repository.fetchConsents();
    switch (result) {
      case ApiSuccess(:final data):
        final raw = data['consents'];
        final consents = raw is List
            ? raw.cast<Map<String, dynamic>>()
            : <Map<String, dynamic>>[];
        emit(
          ConsentLoaded(
            consents: consents,
            cguVersionAccepted: data['cgu_version_accepted'] as String?,
            currentCguVersion: data['current_cgu_version'] as String? ?? '1.0',
          ),
        );
      case ApiFailure(:final message):
        emit(ConsentFailure(message: message));
    }
  }

  Future<void> updateOptIns(Map<String, bool> updates) async {
    emit(const ConsentUpdating());
    final result = await _repository.updateOptIns(updates);
    switch (result) {
      case ApiSuccess():
        emit(const ConsentSuccess(message: 'Préférences mises à jour.'));
      case ApiFailure(:final message):
        emit(ConsentFailure(message: message));
    }
  }

  Future<void> reconsent(Map<String, bool> consents) async {
    emit(const ConsentUpdating());
    final result = await _repository.reconsent(consents);
    switch (result) {
      case ApiSuccess():
        emit(const ConsentSuccess(message: 'CGU acceptées avec succès.'));
      case ApiFailure(:final message):
        emit(ConsentFailure(message: message));
    }
  }
}
