# -*- coding: utf-8 -*-
import os
import django
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'core.settings')
django.setup()

from sector.models import Sector
from users.models import Users

admin_sector, created = Sector.objects.get_or_create(
    nome_setor="Gestão/Administração",
    defaults={'nome_departamento': 'Administração Geral'}
)

print(f"Setor Gestão/Administração criado/encontrado (ID: {admin_sector.id}).")

updated_count = Users.objects.filter(id_setor__isnull=True).update(id_setor=admin_sector)
print(f"Usuários sem setor atualizados: {updated_count}")
