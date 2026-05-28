from django.urls import path
from . import views

urlpatterns = [    
    path('courses/', views.CourseCreateListView.as_view(), name='course-create-list'),

    path('courses/<int:pk>', views.CourseRetrieveUpdateDestroy.as_view(), name='course-details-view'),
]