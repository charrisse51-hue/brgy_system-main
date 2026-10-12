@vite(['resources/css/modern-ui.css', 'resources/js/modern-ui.js'])

@php $uiFeedback = ['success' => session('success'), 'error' => session('error') ?: ($errors->any() ? $errors->first() : null)]; @endphp
<script type="application/json" id="ui-feedback">@json($uiFeedback)</script>
