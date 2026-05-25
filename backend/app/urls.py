from django.contrib import admin
from django.urls import path, include

# 1. IMPORT NOVO PARA O LOGIN (JWT)
from rest_framework_simplejwt.views import (
    TokenObtainPairView,
    TokenRefreshView,
)

# 2. Importamos as Views do Setor da nova pasta 'sectors'
from sector.views import SectorCreateListView, SectorRetrieveUpdateDestroyView, IndicadorCreateListView, IndicadorValorCreateListView

# 3. Importamos as Views de Usuário da pasta 'users'
from users.views import UsersCreateListView, UsersRetrieveUpdateDestroyView
from projects.views import ProjectCreateListView # (Se você já está usando include embaixo, talvez nem precise deste import)

from users.views import CustomLoginView

urlpatterns = [
    path('admin/', admin.site.urls),

    # --- ROTAS DE LOGIN (CUSTOMIZADA PARA USERS) ---
    path('api/login/', CustomLoginView.as_view(), name='custom_login'),

    # --- Rotas dos Setores ---
    path('setores/', SectorCreateListView.as_view(), name='sector-create-list'),
    path('setores/<int:pk>/', SectorRetrieveUpdateDestroyView.as_view(), name='sector-detail-view'),
    path('indicadores/', IndicadorCreateListView.as_view(), name='indicador-create-list'),
    path('indicadores-valores/', IndicadorValorCreateListView.as_view(), name='indicador-valor-create-list'),

    # --- Rotas dos Usuários ---
    path('usuarios/', UsersCreateListView.as_view(), name='users-create-list'),
    path('usuarios/<int:pk>/', UsersRetrieveUpdateDestroyView.as_view(), name='users-detail-view'),

    # --- Rotas de Projetos ---
    path('projetos/', include('projects.urls')),
]