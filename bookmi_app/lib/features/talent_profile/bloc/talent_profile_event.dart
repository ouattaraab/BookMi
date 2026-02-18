sealed class TalentProfileEvent {
  const TalentProfileEvent();
}

final class TalentProfileFetched extends TalentProfileEvent {
  const TalentProfileFetched({required this.slug});
  final String slug;
}

final class TalentProfileRefreshed extends TalentProfileEvent {
  const TalentProfileRefreshed();
}
