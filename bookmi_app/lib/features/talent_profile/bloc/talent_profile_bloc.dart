import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/talent_profile/bloc/talent_profile_event.dart';
import 'package:bookmi_app/features/talent_profile/bloc/talent_profile_state.dart';
import 'package:bookmi_app/features/talent_profile/data/repositories/talent_profile_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class TalentProfileBloc extends Bloc<TalentProfileEvent, TalentProfileState> {
  TalentProfileBloc({required TalentProfileRepository repository})
    : _repository = repository,
      super(const TalentProfileInitial()) {
    on<TalentProfileFetched>(_onFetched);
    on<TalentProfileRefreshed>(_onRefreshed);
  }

  final TalentProfileRepository _repository;
  String? _currentSlug;

  Future<void> _onFetched(
    TalentProfileFetched event,
    Emitter<TalentProfileState> emit,
  ) async {
    if (state is TalentProfileLoading) return;

    _currentSlug = event.slug;
    emit(const TalentProfileLoading());

    final result = await _repository.getTalentBySlug(event.slug);
    switch (result) {
      case ApiSuccess(:final data):
        emit(
          TalentProfileLoaded(
            profile: data.profile,
            similarTalents: data.similarTalents,
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(TalentProfileFailure(code: code, message: message));
    }
  }

  Future<void> _onRefreshed(
    TalentProfileRefreshed event,
    Emitter<TalentProfileState> emit,
  ) async {
    if (_currentSlug == null) return;
    if (state is TalentProfileLoading) return;

    emit(const TalentProfileLoading());

    final result = await _repository.getTalentBySlug(_currentSlug!);
    switch (result) {
      case ApiSuccess(:final data):
        emit(
          TalentProfileLoaded(
            profile: data.profile,
            similarTalents: data.similarTalents,
          ),
        );
      case ApiFailure(:final code, :final message):
        emit(TalentProfileFailure(code: code, message: message));
    }
  }
}
