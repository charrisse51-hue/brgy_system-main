document.addEventListener('DOMContentLoaded', () => {
 const create=document.getElementById('createAccountDialog');
 const edit=document.getElementById('editAccountDialog');
 if(!create || !edit)return;
 function open(dialog,values={},url=null){
  const form=dialog.querySelector('form');form.reset();
  for(const [key,value] of Object.entries(values)){
   const input=form.elements.namedItem(key);if(input && !['password','password_confirmation'].includes(key))input.value=typeof value==='boolean'?(value?'1':'0'):(value??'');
  }
  form.elements.namedItem('_account_id').value=values.id??values._account_id??'';
  form.dataset.originalActive=values.is_active===false || String(values.is_active)==='0'?'0':'1';
  if(url)form.action=url;
  const legacy=dialog.querySelector('[data-legacy-name]');
  legacy.textContent=values.name && !values.first_name && !values.last_name?`Existing full name: ${values.name}. Leave the name fields blank to preserve it.`:'';
  if(dialog===edit){for(const key of ['first_name','last_name','username'])form.elements.namedItem(key).required=Boolean(values[key]);}
  if(dialog===edit)form.elements.namedItem('role_display').value=form.elements.namedItem('role').value==='admin'?'Administrator':'Staff Member';
  dialog.showModal();
 }
 document.querySelector('[data-open-create]').addEventListener('click',()=>open(create));
 document.querySelectorAll('[data-edit-user]').forEach(button=>button.addEventListener('click',()=>open(edit,JSON.parse(button.dataset.editUser),button.dataset.updateUrl)));
 document.querySelectorAll('[data-close-dialog]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog').close()));
 document.querySelectorAll('[data-account-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(form.dataset.accountConfirm))event.preventDefault();}));
 document.querySelectorAll('[data-account-form]').forEach(form=>{
  const password=form.elements.namedItem('password'),confirmation=form.elements.namedItem('password_confirmation');
  const match=()=>confirmation?.setCustomValidity(password.value===confirmation.value?'':'Passwords must match.');
  password?.addEventListener('input',match);confirmation?.addEventListener('input',match);
  form.addEventListener('submit',event=>{
   if(form.dataset.accountForm==='edit' && form.dataset.originalActive==='1' && form.elements.namedItem('is_active').value==='0' && !confirm('Deactivate this account? Existing sessions will end.'))event.preventDefault();
  });
 });
 const state=JSON.parse(document.getElementById('account-restore').textContent);
 if(state.hasErrors && ['create','edit'].includes(state.mode))open(state.mode==='create'?create:edit,state.values,state.mode==='edit'?`${state.updateBase}/${encodeURIComponent(state.values._account_id)}`:null);
});
