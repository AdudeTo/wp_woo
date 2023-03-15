const yaraMainHolder = document.getElementById('yaraMainHolder');
const yaraPluginDirUrl = advanced_script_vars['pluginDirUrl'];
const yaraWPcontentBlock = document.getElementById('wpwrap');
console.log("init yara page!");
console.log(yaraMainHolder);


let yaraMessagesHeader = '';
let yaraMessagesContent = '';
let yaraMessagesBlock, yaraIsMessageActive;


const messagesData = [];
messagesData['welcome'] = { header: "Yara Plugin Is Active!", content: "Checking Cron Job Status" };
messagesData['postcreate'] = {postinprogress: "Post in Progress", postdone: "Post was created", imageinprogress: "Image in progress", imagedone: "Image was created"};








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
        setTimeout(() => {
            yaraMessagesBlock.classList.remove("yaraMessagesFly");
        }, "6100");
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

function yara_product_constructor(yaraData) {
    console.log(yaraData);

    //yaraType

    let productHTML =
        `    
    <h3>${yaraData.title}</h3>
    <h4>product type: ${yaraData.yaraType}</h4>
        <figure>
            <img src="${yaraData.thumbnail}" >
        </figure>
        <p>
        ${yaraData.description}
        </br>
        <strong>price: ${yaraData.price} лв.</strong>
        </p>
        <i></br></br><strong><u>status:</u></strong></br></i>
    `
    return productHTML;
}

let yaraProductsStats;
function yara_build_products_list() {
    if (yaraMainHolder) {
        const productsList = document.createElement('div');
        productsList.id = 'productsList';
        productsList.className = 'productsList';
        yaraMainHolder.append(productsList);

        advanced_script_vars['itemsData'].forEach(async (product, index) => {
            //console.log(index);
            //console.log(product);

            // let productsListContainer = document.getElementById(productsList);

            if (index % 3 == 0) {
                console.log("main product");
            }

            let itemBlock = document.createElement('div');
            itemBlock.id = `yaraItem-${product.id}`;
            itemBlock.className = 'yaraItemBlock';
            
            if (index % 3 == 0) {
                console.log("main product");
                itemBlock.classList.add("yaraMainProduct");
                product.yaraType = "Main Product";
            } else {
                itemBlock.classList.add("yaraOptionProduct");
                product.yaraType = "Child Product";
            }
            itemBlock.innerHTML = yara_product_constructor(product);
            productsList.append(itemBlock);

        });
    }
    loadYaraCronProducts();
    yaraProductsStats = setInterval(loadYaraCronProducts, 30000);    
}



async function loadYaraCronProducts() {
    const response = await fetch(`${yaraPluginDirUrl}/assets/php/requests.php?p=stats`);
    const products = await response.json();
    //console.log(products);  
    let result = Object.keys(products).map((key) => products[key]);
    result.forEach(async (item, n) => {
        console.log(item);
        let currentItem = document.getElementById(`yaraItem-${item.souce_id}`);
        let currentItemImage = document.getElementById(`yaraItem-${item.souce_id}`).querySelector('img');

        //yaraObj.innerHTML = retunr_message(yaraData.header, yaraData.content);
        //messagesData['postcreate'] = {postinprogress: "Post in Progress", postdone: "Post was created", imageinprogress: "Image in progress", imagedone: "Image was created"};
    
        currentItem.classList.remove('inProgress');
        currentItemImage.classList.remove('inProgress');
        let postStatus = "</br></br><strong><u>status:</u></strong></br>";

        if(item.wp_id == 0){
            postStatus += messagesData['postcreate'].postinprogress + "</br>";
            currentItem.classList.add('inProgress');
        }else {
            postStatus +="<span class='green'><strong>" +  messagesData['postcreate'].postdone + "</strong></span></br>";           
        }

        if(item.image_att_id == 0){
            postStatus += messagesData['postcreate'].imageinprogress + "</br>";
            currentItemImage.classList.add('inProgress');
        }else {
            postStatus +="<span class='green'><strong>" +  messagesData['postcreate'].imagedone + "</strong></span></br>";
        } 

        currentItem.querySelector('i').innerHTML =postStatus;

    });
  }






yara_build_products_list();