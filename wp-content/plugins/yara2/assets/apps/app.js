const yaraMainHolder = document.getElementById('yaraMainHolder');
const yaraPluginDirUrl = advanced_script_vars['pluginDirUrl'];
const yaraWPcontentBlock = document.getElementById('wpwrap');


let yaraMessagesHeader = '';
let yaraMessagesContent = '';
let yaraMessagesBlock, yaraIsMessageActive;


const messagesData = [];
messagesData['welcome'] = { header: "Yara Plugin Is Active!", content: "Checking Cron Job Status" };


function yara_set_cookie(cname, cvalue, exmin) {
    const d = new Date();
    d.setTime(d.getTime() + (exmin * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function yara_get_cookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}


function yara_messages_holder() {
    const messages = document.createElement('div');
    messages.id = 'yaraMessages';
    messages.className = 'yaraMessages';
    yaraWPcontentBlock.append(messages);

    let isMSGplayed = yara_get_cookie('yaraWelcomeMessage');
    if (!isMSGplayed) {
        yaraMessagesBlock = document.getElementById('yaraMessages');
        object_innerHTML_set(yaraMessagesBlock, messagesData['welcome']);
        yaraMessagesBlock.classList.add("yaraMessagesFly");
        yara_set_cookie('yaraWelcomeMessage', 1, 5);
    }
   
}

yara_messages_holder();

function object_innerHTML_clear(yaraObj) {
    yaraObj.innerHTML = '';
}

function object_innerHTML_set(yaraObj, yaraData) {
    object_innerHTML_clear(yaraObj);
    yaraObj.innerHTML = retunr_message(yaraData.header, yaraData.content);
}

function retunr_message(header, content) {
    let myMessage = `<h4>${header}</h4><p>${content}</p>`
    return myMessage;
}