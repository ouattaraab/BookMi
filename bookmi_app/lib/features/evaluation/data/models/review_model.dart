class ReviewModel {
  const ReviewModel({
    required this.id,
    required this.bookingRequestId,
    required this.reviewerId,
    required this.revieweeId,
    required this.type,
    required this.rating,
    this.comment,
    this.reply,
    this.replyAt,
    this.createdAt,
  });

  factory ReviewModel.fromJson(Map<String, dynamic> json) {
    return ReviewModel(
      id: json['id'] as int,
      bookingRequestId: json['booking_request_id'] as int,
      reviewerId: json['reviewer_id'] as int,
      revieweeId: json['reviewee_id'] as int,
      type: json['type'] as String,
      rating: json['rating'] as int,
      comment: json['comment'] as String?,
      reply: json['reply'] as String?,
      replyAt: json['reply_at'] as String?,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
    );
  }

  final int id;
  final int bookingRequestId;
  final int reviewerId;
  final int revieweeId;

  /// `client_to_talent` or `talent_to_client`
  final String type;
  final int rating;
  final String? comment;

  /// Talent's reply to a client_to_talent review.
  final String? reply;

  /// ISO-8601 string of when the reply was posted (null if not yet replied).
  final String? replyAt;

  final DateTime? createdAt;
}
