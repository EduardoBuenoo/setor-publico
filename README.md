# Plataforma Inteligente para Análise e Gestão de Processos do Departamento de Desenvolvimento Econômico

Sistema desenvolvido por estudantes do 5° período de Sistemas de Informação, para auxiliar o Departamento de Desenvolvimento Econômico da Prefeitura Municipal de Iracemápolis no gerenciamento de indicadores, projetos, ofícios e produtividade dos setores públicos.

---

# 📌 Sobre o Projeto

O projeto foi desenvolvido com foco na modernização da gestão pública, permitindo centralizar informações, acompanhar indicadores e otimizar processos internos do departamento.

A plataforma permite que colaboradores dos setores:

- Banco do Povo
- PAT
- SEBRAE
- PROCON

registrem suas produções, atividades, projetos e ofícios, enquanto gestores acompanham dados por dashboards e relatórios gerenciais.

---

# 👥 Equipe

Equipe Gafia:

- Sofia Camargo Nunes
- Giovana Jacobucci
- Kael Vicente Dipres
- Virna Karina do Amaral Pereira

---

# 🚀 Funcionalidades

## 👤 Módulo de Usuários

- Cadastro de usuários
- Controle de níveis de acesso
- Login e autenticação
- Alteração e redefinição de senha
- Registro de logs de alteração
- Consulta e filtro de usuários
- Exclusão de usuários

---

## 📊 Módulo de Indicadores

- Cadastro de indicadores
- Registro de atividades
- Dashboards gráficos
- Gráficos automáticos conforme tipo do indicador
- Cards métricos
- Filtros por período e setor
- Exportação de relatórios PDF

---

## 📄 Módulo de Ofícios

- Cadastro de ofícios
- Numeração automática e sequencial
- Associação automática de usuário e setor
- Consulta e filtros de ofícios

---

## 📁 Módulo de Projetos

- Cadastro de projetos
- Gestão de tarefas
- Controle de prioridade
- Tags de status
- Percentual de conclusão
- Gráfico de Gantt
- Visualização de cronogramas
- Controle por setor e nível de acesso

---

# 🛠️ Tecnologias Utilizadas

## Backend

- Python
- Django
- Django REST Framework

## Frontend

- HTML5
- CSS3
- JavaScript

## Banco de Dados

- PostgreSQL

---

# 🔐 Requisitos Não Funcionais

- Autenticação obrigatória
- Senhas criptografadas
- Compatibilidade HTTPS
- Responsividade
- Integração REST API
- Interface intuitiva e acessível

---

# 🧩 Arquitetura do Sistema

O sistema utiliza arquitetura distribuída baseada em:

- Frontend Web
- Backend REST API
- Banco de Dados PostgreSQL

A comunicação ocorre via requisições HTTP/HTTPS entre cliente e servidor.

---

# 📈 Dashboards e Indicadores

O sistema gera dashboards automaticamente conforme o tipo de dado do indicador:

| Tipo do Indicador | Tipo de Gráfico |
|-------------------|----------------|
| Número            | Barras |
| Tempo             | Linha |
| Porcentagem       | Donut |

---

# 📂 Estrutura dos Principais Módulos

## Usuários
Gerenciamento de acesso e autenticação.

## Indicadores
Controle de produtividade dos setores.

## Projetos
Gestão de tarefas e cronogramas.

## Ofícios
Registro e rastreabilidade documental.

---

# 🔄 Metodologia de Desenvolvimento

O projeto utiliza:

- Modelo Evolutivo
- Metodologia Ágil
- Scrum

Com reuniões de:

- Sprint Planning
- Daily Scrum
- Sprint Review
- Sprint Retrospective

---

# 📡 Endpoints Principais

## Setores

```http
POST /setores/
GET /setores/
```

## Usuários

```http
POST /usuarios/
GET /usuarios/
```

## Projetos

```http
POST /projetos/
GET /projetos/
```

---

# 📥 Exemplo JSON — Cadastro de Usuário

```json
{
  "nome": "João Silva",
  "funcao": "Auxiliar Administrativo",
  "matricula": 202610,
  "id_setor": 1
}
```

---

# 🗂️ Estrutura de Dados

O sistema possui os seguintes Arquivos Lógicos Internos (ALIs):

- Usuários
- Histórico de Senhas
- Setores
- Indicadores
- Atividades
- Ofícios
- Projetos
- Tarefas

---

# 🔗 Links do Projeto

## Cronograma

https://github.com/users/sofia-camargo/projects/2/views/1

---

# 📚 Considerações Finais

O projeto busca fornecer uma solução tecnológica acessível para modernizar a gestão pública municipal, promovendo maior organização, acompanhamento de produtividade, rastreabilidade documental e apoio à tomada de decisão.

---
