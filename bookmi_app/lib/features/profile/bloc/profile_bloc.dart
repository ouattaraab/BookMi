import 'package:bloc/bloc.dart';
import 'package:bookmi_app/core/network/api_result.dart';
import 'package:bookmi_app/features/profile/data/repositories/profile_repository.dart';

part 'profile_event.dart';
part 'profile_state.dart';

class ProfileBloc extends Bloc<ProfileEvent, ProfileState> {
  ProfileBloc({required ProfileRepository repository})
    : _repository = repository,
      super(const ProfileInitial()) {
    on<ProfileStatsFetched>(_onStatsFetched);
  }

  final ProfileRepository _repository;

  Future<void> _onStatsFetched(
    ProfileStatsFetched event,
    Emitter<ProfileState> emit,
  ) async {
    emit(const ProfileLoading());
    final result = await _repository.getStats(isTalent: event.isTalent);
    switch (result) {
      case ApiSuccess(:final data):
        emit(ProfileLoaded(stats: data));
      case ApiFailure(:final message):
        emit(ProfileFailure(message: message));
    }
  }
}
