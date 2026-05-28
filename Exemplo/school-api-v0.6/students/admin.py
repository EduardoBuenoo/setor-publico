from django.contrib import admin
from students.models import Student


class StudentAdmin(admin.ModelAdmin):
    list_display = ('name', 'date_of_birth', 'course',)
    search_fields = ('name',)


admin.site.register(Student, StudentAdmin)
