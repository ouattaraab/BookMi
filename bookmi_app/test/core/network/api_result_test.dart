import 'package:bookmi_app/core/network/api_result.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ApiResult', () {
    group('ApiSuccess', () {
      test('holds data', () {
        const result = ApiSuccess<String>('hello');
        expect(result.data, 'hello');
      });

      test('is a subtype of ApiResult', () {
        const result = ApiSuccess<int>(42);
        expect(result, isA<ApiResult<int>>());
      });

      test('works with complex types', () {
        const result = ApiSuccess<Map<String, dynamic>>(
          {'id': 1, 'name': 'test'},
        );
        expect(result.data['id'], 1);
        expect(result.data['name'], 'test');
      });
    });

    group('ApiFailure', () {
      test('holds code and message', () {
        const result = ApiFailure<String>(
          code: 'NOT_FOUND',
          message: 'Resource not found',
        );
        expect(result.code, 'NOT_FOUND');
        expect(result.message, 'Resource not found');
        expect(result.details, isNull);
      });

      test('is a subtype of ApiResult', () {
        const result = ApiFailure<int>(
          code: 'ERROR',
          message: 'Something went wrong',
        );
        expect(result, isA<ApiResult<int>>());
      });

      test('holds optional details', () {
        const result = ApiFailure<String>(
          code: 'VALIDATION',
          message: 'Validation failed',
          details: {'field': 'email', 'error': 'invalid'},
        );
        expect(result.details, isNotNull);
        expect(result.details!['field'], 'email');
      });
    });

    group('exhaustive matching', () {
      test('switch covers all cases', () {
        const ApiResult<String> success = ApiSuccess('data');
        const ApiResult<String> failure = ApiFailure(
          code: 'ERR',
          message: 'error',
        );

        final successResult = switch (success) {
          ApiSuccess(:final data) => data,
          ApiFailure(:final message) => message,
        };
        expect(successResult, 'data');

        final failureResult = switch (failure) {
          ApiSuccess(:final data) => data,
          ApiFailure(:final message) => message,
        };
        expect(failureResult, 'error');
      });
    });
  });
}
