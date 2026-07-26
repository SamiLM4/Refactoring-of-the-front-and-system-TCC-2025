function createPermissionChecker(usuario) {
    const isAdmin = usuario.admin_owner === true;
    const permissoes = new Set(usuario.permissoes || []);

    return function hasPermission(perm) {
        if (isAdmin) return true;

        const [recurso, acao] = perm.split(".");

        return [...permissoes].some(p => {
            const [recursoPerm, acaoPerm] = p.split(".");
            return recursoPerm.startsWith(recurso) && acaoPerm === acao;
        });
    };
}