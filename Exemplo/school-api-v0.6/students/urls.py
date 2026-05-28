from django.urls import path
from . import views

urlpatterns = [    
    path('students/', views.StudentCreateListView.as_view(), name='student-create-list'),

    path('students/<int:pk>', views.StudentRetrieveUpdateDestroy.as_view(), name='student-detail-view'),  
]