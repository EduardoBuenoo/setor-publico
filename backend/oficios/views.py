from rest_framework import generics
from .models import Oficios
from .serializers import OficiosSerializer
import datetime
from django.db.models import Max

class OficiosListCreateView(generics.ListCreateAPIView):
    queryset = Oficios.objects.all().order_by('-data_registro', '-id')
    serializer_class = OficiosSerializer

    def perform_create(self, serializer):
        ano = datetime.datetime.now().year
        data_registro = datetime.datetime.now().date()
        
        # Obter o maximo numero_oficio do ano atual
        max_num = Oficios.objects.filter(ano=ano).aggregate(Max('numero_oficio'))['numero_oficio__max']
        numero_oficio = (max_num or 0) + 1

        serializer.save(ano=ano, data_registro=data_registro, numero_oficio=numero_oficio)

class OficiosRetrieveUpdateDestroyView(generics.RetrieveUpdateDestroyAPIView):
    queryset = Oficios.objects.all()
    serializer_class = OficiosSerializer
