from django.contrib import admin
from teachers.models import Teacher

class TeacherAdmin(admin.ModelAdmin):
    list_display = ('name', 'date_of_birth', 'hire_date',)
    search_fields = ('name',)

admin.site.register(Teacher, TeacherAdmin)
