import psycopg2
try:
    conn = psycopg2.connect(dbname="pimois", user="postgres", password="250226", host="localhost", port="5432")
    print("Conexão bem-sucedida!")
except Exception as e:
    print("Erro bruto:")
    print(repr(e))
