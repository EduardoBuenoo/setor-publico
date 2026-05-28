from rest_framework import serializers
from .models import Sector, Indicador, IndicadorValor

class SectorSerializer(serializers.ModelSerializer):
    class Meta:
        model = Sector
        fields = '__all__'

class IndicadorSerializer(serializers.ModelSerializer):
    class Meta:
        model = Indicador
        fields = '__all__'

class IndicadorValorSerializer(serializers.ModelSerializer):
    class Meta:
        model = IndicadorValor
        fields = '__all__'
