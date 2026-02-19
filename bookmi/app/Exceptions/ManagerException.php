<?php

namespace App\Exceptions;

class ManagerException extends BookmiException
{
    public static function managerNotFound(): self
    {
        return new self(
            'MANAGER_NOT_FOUND',
            'Aucun manager trouvé avec cet email.',
            404,
        );
    }

    public static function notAManager(): self
    {
        return new self(
            'MANAGER_ROLE_REQUIRED',
            "Cet utilisateur n'a pas le rôle manager.",
            422,
        );
    }

    public static function alreadyAssigned(): self
    {
        return new self(
            'MANAGER_ALREADY_ASSIGNED',
            'Ce manager est déjà assigné à ce talent.',
            422,
        );
    }

    public static function notAssigned(): self
    {
        return new self(
            'MANAGER_NOT_ASSIGNED',
            "Ce manager n'est pas assigné à ce talent.",
            403,
        );
    }

    public static function noManagerAssigned(): self
    {
        return new self(
            'MANAGER_NONE_ASSIGNED',
            "Ce talent n'a pas de manager assigné.",
            404,
        );
    }

    public static function unauthorized(): self
    {
        return new self(
            'MANAGER_UNAUTHORIZED',
            "Vous n'êtes pas autorisé à effectuer cette action pour ce talent.",
            403,
        );
    }

    public static function cannotManageOwnConversation(): self
    {
        return new self(
            'MANAGER_NOT_PARTICIPANT',
            "Ce talent ne participe pas à cette conversation.",
            403,
        );
    }
}
