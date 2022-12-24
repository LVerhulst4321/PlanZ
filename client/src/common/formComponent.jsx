import React from 'react';

class FormComponent extends React.Component {

    render() {
        return undefined;
    }

    getFormValue(formName) {
        if (this.state.values) {
            return this.state.values[formName] || '';
        } else {
            return '';
        }
    }

    setFormValue(formName, formValue) {
        let state = this.state;
        let value = state.values || {};
        let newValue = { ...value };
        let errors = this.state.errors || {};
        newValue[formName] = formValue;
        errors[formName] = !this.validateValue(formName, formValue);

        this.setState((state) => ({
            ...state,
            values: newValue,
            message: null,
            errors: errors
        }));
    }

    validateValue(formName, formValue) {
        return true;
    }

    getErrorClass(name) {
        return this.isFieldInError(name) ? "is-invalid" : "";
    }

    isFieldInError(name) {
        let errors = this.state.errors;
        if (errors) {
            return errors[name];
        } else {
            return false;
        }
    }

    getFormFields() {
        return []
    }

    isValidForm() {
        let formKeys = this.getFormFields()
        let errors = this.state.errors || {};
        let valid = true
        formKeys.forEach(element => {
            let v = this.validateValue(element, this.state.values[element]);
            valid &= v;
            errors[element] = !v;
        });

        let message = null;
        if (!valid) {
            message = { severity: "danger", text: "Gosh willikers! It looks like some of this information isn't right."}
        }
        this.setState({
            ...this.state,
            errors: errors,
            message: message
        })
        return valid;
    }

    getAllFormValues() {
        return this.state.values;
    }
}

export default FormComponent;