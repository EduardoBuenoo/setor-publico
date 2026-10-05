from datetime import date
from rest_framework import serializers
from django.db.models import Sum
from .models import Sector, Indicador, Atividade


class SectorSerializer(serializers.ModelSerializer):
    class Meta:
        model = Sector
        fields = '__all__'


class IndicadorSerializer(serializers.ModelSerializer):
    valor_total = serializers.SerializerMethodField()

    class Meta:
        model = Indicador
        fields = ['id_indicador', 'id_setor', 'nome', 'tipo_dado', 'valor_total']

    def get_valor_total(self, obj):
        total = obj.atividade_set.aggregate(total=Sum('valor'))['total']
        return total or 0


class AtividadeSerializer(serializers.ModelSerializer):
    class Meta:
        model = Atividade
        fields = '__all__'
        read_only_fields = ['id_usuario']

    def validate_data_cadastro(self, value):
        # RN12
        if value > date.today():
            raise serializers.ValidationError('A data de cadastro não pode ser no futuro.')
        return value
