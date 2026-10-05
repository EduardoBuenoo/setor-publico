from rest_framework import generics
from rest_framework.permissions import IsAuthenticated

from .models import Sector, Indicador, Atividade
from .serializers import SectorSerializer, IndicadorSerializer, AtividadeSerializer


class SectorCreateListView(generics.ListCreateAPIView):
    queryset = Sector.objects.all()
    serializer_class = SectorSerializer
    permission_classes = [IsAuthenticated]


class SectorRetrieveUpdateDestroyView(generics.RetrieveUpdateDestroyAPIView):
    queryset = Sector.objects.all()
    serializer_class = SectorSerializer
    permission_classes = [IsAuthenticated]


class IndicadorCreateListView(generics.ListCreateAPIView):
    serializer_class = IndicadorSerializer
    permission_classes = [IsAuthenticated]

    def get_queryset(self):
        usuario = self.request.user
        qs = Indicador.objects.all()
        if usuario.nivel_acesso == 'Administrador':
            return qs
        return qs.filter(id_setor=usuario.id_setor)

    def perform_create(self, serializer):
        usuario = self.request.user
        if usuario.nivel_acesso == 'Administrador':
            serializer.save()
        else:
            serializer.save(id_setor=usuario.id_setor)


class AtividadeCreateListView(generics.ListCreateAPIView):
    serializer_class = AtividadeSerializer
    permission_classes = [IsAuthenticated]

    def get_queryset(self):
        usuario = self.request.user
        qs = Atividade.objects.select_related('id_indicador', 'id_usuario').order_by('-data_cadastro')
        if usuario.nivel_acesso == 'Administrador':
            return qs
        return qs.filter(id_indicador__id_setor=usuario.id_setor)

    def perform_create(self, serializer):
        # O responsável pelo lançamento é sempre o usuário autenticado.
        serializer.save(id_usuario=self.request.user)


class AtividadeRetrieveUpdateDestroyView(generics.RetrieveUpdateDestroyAPIView):
    serializer_class = AtividadeSerializer
    permission_classes = [IsAuthenticated]

    def get_queryset(self):
        usuario = self.request.user
        qs = Atividade.objects.select_related('id_indicador', 'id_usuario')
        if usuario.nivel_acesso == 'Administrador':
            return qs
        return qs.filter(id_indicador__id_setor=usuario.id_setor)
