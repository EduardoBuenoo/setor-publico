from rest_framework.permissions import BasePermission


class IsAdministrador(BasePermission):
    message = "Apenas administradores podem realizar esta operação."

    def has_permission(self, request, view):
        return (
            request.user
            and request.user.is_authenticated
            and request.user.nivel_acesso == 'Administrador'
        )


class IsAdministradorOuGestor(BasePermission):
    message = "Você não possui permissão para realizar esta operação."

    def has_permission(self, request, view):
        return (
            request.user
            and request.user.is_authenticated
            and request.user.nivel_acesso in [
                'Administrador',
                'Gestor'
            ]
        )