import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth.user);

    const can = (permission) => {
        if (! user.value) {
            return false;
        }

        if (user.value.is_super_admin) {
            return true;
        }

        return user.value.permissions?.includes(permission) ?? false;
    };

    return { user, can };
}
