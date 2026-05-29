from rest_framework import serializers
from sector.models import Oficio

class OficiosSerializer(serializers.ModelSerializer):
    class Meta:
        model = Oficio
        fields = '__all__'
        read_only_fields = ['numero_oficio', 'ano', 'data_registro']
