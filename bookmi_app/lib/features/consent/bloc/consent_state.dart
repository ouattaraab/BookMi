abstract class ConsentState {
  const ConsentState();
}

class ConsentInitial extends ConsentState {
  const ConsentInitial();
}

class ConsentLoading extends ConsentState {
  const ConsentLoading();
}

class ConsentLoaded extends ConsentState {
  const ConsentLoaded({
    required this.consents,
    required this.cguVersionAccepted,
    required this.currentCguVersion,
  });

  final List<Map<String, dynamic>> consents;
  final String? cguVersionAccepted;
  final String currentCguVersion;
}

class ConsentUpdating extends ConsentState {
  const ConsentUpdating();
}

class ConsentSuccess extends ConsentState {
  const ConsentSuccess({required this.message});
  final String message;
}

class ConsentFailure extends ConsentState {
  const ConsentFailure({required this.message, this.code});
  final String message;
  final String? code;
}
