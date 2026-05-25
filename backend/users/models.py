from django.db import models
from sector.models import Sector 

class Users(models.Model):
    id = models.AutoField(primary_key=True)
    matricula = models.CharField(max_length=100, unique=True)
    nome = models.CharField(max_length=255)
    funcao = models.CharField(max_length=255, null=True, blank=True)
    id_setor = models.ForeignKey(
        'sector.Sector', 
        on_delete=models.CASCADE, 
        db_column='id_setor',
        null=True, blank=True
    )
    nivel_acesso = models.CharField(max_length=50)
    senha = models.CharField(max_length=255)

    class Meta:
        db_table = 'usuarios'

    def __str__(self):
        return self.nome

    @property
    def is_authenticated(self):
        return True