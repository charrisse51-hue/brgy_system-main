window.PersonValidation = {
    validate(input, container, required = false) {
        const key = input.dataset.personField;
        const raw = input.value;
        const value = raw.trim();
        if (input.type !== 'number') input.value = value;
        const active = required || [...container.querySelectorAll('[data-person-field]')].some(field => field.value.trim() !== '' || field.validity.badInput);
        if (['first_name', 'middle_name', 'last_name'].includes(key)) {
            const label = {first_name:'First name',middle_name:'Middle name',last_name:'Last name'}[key];
            if (!value) return active && key !== 'middle_name' ? `${label} is required.` : null;
            if (/\p{N}/u.test(value)) return `Numbers are not allowed in the ${label.toLowerCase()}.`;
            if (!/^[\p{L}\p{M} '\u2019\-]+$/u.test(value) || !/\p{L}/u.test(value)) return `${label} may contain only letters, spaces, apostrophes, and hyphens.`;
            if (key !== 'middle_name' && [...value].length < 2) return `${label} must contain at least 2 characters.`;
            if ([...value].length > 50) return `${label} cannot exceed 50 characters.`;
        }
        if (key === 'age' && (input.validity.badInput || (value && (!/^[0-9]+$/.test(value) || Number(value) > 120)))) return 'Please enter a valid age.';
        if (key === 'address' && ((raw && !value) || (value && ([...value].length > 255 || !/^[\p{L}\p{M}\p{N} .,\u2019'#()\/\-]+$/u.test(value) || !/[\p{L}\p{N}]/u.test(value))))) return 'Please enter a valid address (maximum 255 characters).';
        return null;
    }
};
