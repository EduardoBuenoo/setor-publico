from django.db import models

class Sector(models.Model):
    id = models.AutoField(primary_key=True)
    nome_setor = models.CharField(max_length=255)
    sigla = models.CharField(max_length=50)

    class Meta:
        db_table = 'setores'

    def __str__(self):
        return self.nome_setor

class Oficio(models.Model):
    id = models.AutoField(primary_key=True)
    numero_oficio = models.IntegerField()
    ano = models.IntegerField()
    data_registro = models.DateField()
    assunto = models.TextField()
    local_fisico = models.CharField(max_length=255, null=True, blank=True)
    id_usuario = models.ForeignKey('users.Users', on_delete=models.CASCADE, db_column='id_usuario', null=True, blank=True)
    id_setor = models.ForeignKey(Sector, on_delete=models.CASCADE, db_column='id_setor', null=True, blank=True)

    class Meta:
        db_table = 'oficios'

class Atividade(models.Model):
    id = models.AutoField(primary_key=True)
    indicador = models.CharField(max_length=255)
    data_registro = models.DateField()
    id_usuario = models.ForeignKey('users.Users', on_delete=models.CASCADE, db_column='id_usuario', null=True, blank=True)
    id_setor = models.ForeignKey(Sector, on_delete=models.CASCADE, db_column='id_setor', null=True, blank=True)

    class Meta:
        db_table = 'atividades'

class Indicador(models.Model):
    id = models.AutoField(primary_key=True)
    nome = models.CharField(max_length=255)
    tipo = models.CharField(max_length=50)
    id_setor = models.ForeignKey(Sector, on_delete=models.CASCADE, db_column='id_setor', null=True, blank=True)

    class Meta:
        db_table = 'indicadores'

class IndicadorValor(models.Model):
    id = models.AutoField(primary_key=True)
    id_indicador = models.ForeignKey(Indicador, on_delete=models.CASCADE, db_column='id_indicador')
    valor = models.DecimalField(max_digits=10, decimal_places=2)
    data_registro = models.DateField()
    tipo_registro = models.CharField(max_length=50)
    id_usuario = models.ForeignKey('users.Users', on_delete=models.CASCADE, db_column='id_usuario')

    class Meta:
        db_table = 'indicadores_valores'