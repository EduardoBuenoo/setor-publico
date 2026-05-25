import psycopg2
from psycopg2.extensions import ISOLATION_LEVEL_AUTOCOMMIT

try:
    conn = psycopg2.connect(dbname="postgres", user="postgres", password="123", host="localhost", port="5432")
    conn.set_isolation_level(ISOLATION_LEVEL_AUTOCOMMIT)
    cursor = conn.cursor()
    cursor.execute("DROP DATABASE IF EXISTS pimois;")
    cursor.execute("CREATE DATABASE pimois;")
    cursor.close()
    conn.close()
    print("Banco pimois criado com sucesso!")
except Exception as e:
    print("Erro ao criar banco:", e)
