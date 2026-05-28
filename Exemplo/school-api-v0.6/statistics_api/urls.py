from django.urls import path
from . import views

urlpatterns = [    
   path('statistics/', views.ApiStatsView.as_view(), name='stats-view'),   
]