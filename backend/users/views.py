from rest_framework import generics
from .models import Users
from .serializers import UsersSerializer

class UsersCreateListView(generics.ListCreateAPIView):
    queryset = Users.objects.all()
    serializer_class = UsersSerializer

class UsersRetrieveUpdateDestroyView(generics.RetrieveUpdateDestroyAPIView):
    queryset = Users.objects.all()
    serializer_class = UsersSerializer    

import bcrypt
from rest_framework.views import APIView
from rest_framework.response import Response
from rest_framework import status
from rest_framework_simplejwt.tokens import RefreshToken
from rest_framework.permissions import AllowAny

class CustomLoginView(APIView):
    permission_classes = [AllowAny]

    def post(self, request):
        matricula = request.data.get('matricula')
        senha = request.data.get('senha')

        if not matricula or not senha:
            return Response({'error': 'Matrícula e senha são obrigatórios'}, status=status.HTTP_400_BAD_REQUEST)

        try:
            user = Users.objects.get(matricula=matricula)
            if bcrypt.checkpw(senha.encode('utf-8'), user.senha.encode('utf-8')):
                refresh = RefreshToken()
                refresh['user_id'] = user.id
                return Response({
                    'access': str(refresh.access_token),
                    'user': {
                        'id': user.id,
                        'nome': user.nome,
                        'nivel_acesso': user.nivel_acesso,
                        'id_setor': user.id_setor.id if user.id_setor else None
                    }
                })
            else:
                return Response({'error': 'Credenciais inválidas'}, status=status.HTTP_401_UNAUTHORIZED)
        except Users.DoesNotExist:
            return Response({'error': 'Credenciais inválidas'}, status=status.HTTP_401_UNAUTHORIZED)