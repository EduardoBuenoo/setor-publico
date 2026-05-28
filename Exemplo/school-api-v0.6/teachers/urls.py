from django.urls import path
from . import views

urlpatterns = [    
    path('teachers/', views.TeacherCreateListView.as_view(), name='teacher-create-list'),

    path('teachers/<int:pk>', views.TeacherRetrieveUpdateDestroy.as_view(), name='teacher-detail-view'),
]