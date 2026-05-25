from rest_framework import generics
from rest_framework.permissions import IsAuthenticated  # <-- 1. IMPORTA O CADEADO
from .models import Sector
from .serializers import SectorSerializer

# View para listar todos os setores e criar um novo
class SectorCreateListView(generics.ListCreateAPIView):
    queryset = Sector.objects.all()
    serializer_class = SectorSerializer
    permission_classes = [IsAuthenticated]  # <-- 2. TRANCA ESTA ROTA

# View para ver detalhes, editar e deletar UM setor específico
class SectorRetrieveUpdateDestroyView(generics.RetrieveUpdateDestroyAPIView):
    queryset = Sector.objects.all()
    serializer_class = SectorSerializer
    permission_classes = [IsAuthenticated]  # <-- 3. TRANCA ESTA ROTA TAMBÉM

from rest_framework.permissions import AllowAny
from .models import Indicador, IndicadorValor
from .serializers import IndicadorSerializer, IndicadorValorSerializer

class IndicadorCreateListView(generics.ListCreateAPIView):
    queryset = Indicador.objects.all()
    serializer_class = IndicadorSerializer
    permission_classes = [AllowAny] # Liberado temporariamente para integração com PHP

class IndicadorValorCreateListView(generics.ListCreateAPIView):
    queryset = IndicadorValor.objects.all()
    serializer_class = IndicadorValorSerializer
    permission_classes = [AllowAny] # Liberado temporariamente para integração com PHP