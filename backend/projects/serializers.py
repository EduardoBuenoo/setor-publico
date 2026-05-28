from rest_framework import serializers
from .models import Project, Tarefa

class TarefaSerializer(serializers.ModelSerializer):
    class Meta:
        model = Tarefa
        fields = '__all__'

class ProjetoSerializer(serializers.ModelSerializer):
    tarefas = TarefaSerializer(many=True, read_only=True, source='tarefa_set')
    
    class Meta:
        model = Project
        fields = '__all__'
