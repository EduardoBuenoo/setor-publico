from django.db import models
from courses.models import Course

class Student(models.Model):
    name = models.CharField(max_length=100)
    date_of_birth = models.DateField(null=True, blank=True)

    course = models.ForeignKey(Course, on_delete=models.RESTRICT, related_name='students')

    def __str__(self):
        return self.name
