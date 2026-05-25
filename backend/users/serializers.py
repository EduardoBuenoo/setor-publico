from rest_framework import serializers 
from .models import Users



class UsersSerializer(serializers.ModelSerializer):
    nome_setor = serializers.CharField(source='id_setor.nome_setor', read_only=True)

    class Meta:
        model = Users
        fields = ['id', 'matricula', 'nome', 'funcao', 'id_setor', 'nome_setor', 'nivel_acesso', 'senha']
        extra_kwargs = {
            'senha': {'write_only': True}
        }

    def create(self, validated_data):
        import bcrypt
        if 'senha' in validated_data:
            senha = validated_data['senha']
            validated_data['senha'] = bcrypt.hashpw(senha.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        return super().create(validated_data)

    def update(self, instance, validated_data):
        import bcrypt
        if 'senha' in validated_data:
            senha = validated_data['senha']
            validated_data['senha'] = bcrypt.hashpw(senha.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        return super().update(instance, validated_data)