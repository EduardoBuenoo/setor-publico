from django.db import models

class Oficios(models.Model):
    numero_oficio = models.IntegerField()
    ano = models.IntegerField()
    data_registro = models.DateField()
    assunto = models.TextField()
    id_usuario = models.IntegerField()
    id_setor = models.IntegerField()
    local_fisico = models.CharField(max_length=255, blank=True, null=True)

    class Meta:
        db_table = 'oficios'
        managed = False
