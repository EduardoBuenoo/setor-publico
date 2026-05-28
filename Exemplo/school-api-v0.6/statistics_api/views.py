from rest_framework import views, response, status
from rest_framework.permissions import IsAuthenticated
from students.models import Student
from teachers.models import Teacher
from courses.models import Course


class ApiStatsView(views.APIView):
    permission_classes = (IsAuthenticated,)

    def get(self, request):
        total_students = Student.objects.count()
        total_teachers = Teacher.objects.count()
        total_courses = Course.objects.count()

        return response.Response(
            data = {
                'total_students': total_students,
                'total_teachers': total_teachers,
                'total_courses': total_courses,
            }, status= status.HTTP_200_OK,
        )
