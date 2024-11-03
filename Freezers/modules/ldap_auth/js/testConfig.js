
async function ldap_testConfig(){
  var username  =  document.getElementById('miniorange_ldap_test_account_username').value.trim();
  var password  =  document.getElementById('miniorange_ldap_test_account_password').value;

  let baseurl = window.location.href;
  let pos = baseurl.indexOf("admin");
  let testUrl = baseurl.replace(baseurl.slice(pos), "testLdapConfig");

  let mapForm = document.createElement("form");
  mapForm.target = "Map";
  mapForm.method = "POST";
  mapForm.action = testUrl;

  let mapInput = document.createElement("input");
  mapInput.type = "text";
  mapInput.name = "user";
  mapInput.hidden = true;
  mapInput.value = username;
  mapForm.appendChild(mapInput);

  let mapInput2 = document.createElement('input');
  mapInput2.type = "text";
  mapInput2.name = 'pass';
  mapInput2.hidden = true;
  mapInput2.value = password;
  mapForm.appendChild(mapInput2);

  document.body.appendChild(mapForm);

  map = window.open("", "Map", "status=0,title=0,height=600,width=800,scrollbars=1");

  if (map) {
    mapForm.submit();
  } else {
    alert('You must allow popups for this test authentication to work.');
  }

}