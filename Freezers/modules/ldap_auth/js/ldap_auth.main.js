function Instance_Pricing(x, instances2, instances3) {

    document.getElementById("instances1").value = x;
    const price_standard = { 1: "249", 2: "468", 3: "657", 4: "816", 5: "946", 6: "1065", 7: "1175", 8: "1274", 9: "1364", 10: "1444", 15: "1730", 20: "1897", 30: "2196", 40: "2496", 50: "2793" };
    var actual_price_standard = 249 * x;
    var discount1 = ((actual_price_standard - price_standard[x]) / actual_price_standard) * 100;
    discount1 = Math.floor(discount1);
    if (discount1 !== 0) {
        document.getElementById("standard_price").innerHTML = "<sup>$</sup><strike style=\"color:red;\" >" + actual_price_standard + "</strike>" + price_standard[x];
        document.getElementById("standard_discount").innerHTML = "<div style='font-size: large'><br> [" + discount1 + "% discount ] </div>";
        document.getElementById("standard_discount").style.display = "block";
    } else {
        document.getElementById("standard_price").innerHTML = "<sup>$</sup>" + price_standard[x];
        document.getElementById("standard_discount").style.display = "none";
    }


    document.getElementById("instances2").value = x;
    const price_premium = { 1: "399", 2: "734", 3: "1005", 4: "1212", 5: " 1359", 6: "1476", 7: "1572", 8: "1643", 9: "1691", 10: "1731", 15: "1931", 20: "2130", 30: "2529", 40: "2928", 50: "3327" };
    var actual_price_premium = 399 * x;
    var discount2 = ((actual_price_premium - price_premium[x]) / actual_price_premium) * 100;
    discount2 = Math.floor(discount2);
    if (discount1 !== 0) {
        document.getElementById("premium_price").innerHTML = "<sup>$</sup><strike style=\"color:red;\" >" + actual_price_premium + "</strike>" + price_premium[x];
        document.getElementById("premium_discount").innerHTML = "<div style='font-size: large'><br> [" + discount2 + "% discount ] </div>";
        document.getElementById("premium_discount").style.display = "block";
    } else {
        document.getElementById("premium_price").innerHTML = "<sup>$</sup>" + price_premium[x];
        document.getElementById("premium_discount").style.display = "none";
    }

    document.getElementById("instances3").value = x;
    const price_allinclusive = { 1: "649", 2: "999", 3: "1449", 4: "1799", 5: "2149", 6: "2399", 7: "2649", 8: "2849", 9: "3049", 10: "3149", 15: "3449", 20: "3749", 30: "4349", 40: "4949", 50: "5549" };
    var actual_price_allinclusive = 649 * x;

    var discount3 = ((actual_price_allinclusive - price_allinclusive[x]) / actual_price_allinclusive) * 100;
    discount3 = Math.floor(discount3);
    if (discount1 !== 0) {
        document.getElementById("AllInclusive_price").innerHTML = "<sup>$</sup><strike style=\"color:red;\" >" + actual_price_allinclusive + "</strike>" + price_allinclusive[x];
        document.getElementById("AllInclusive_discount").innerHTML = "<div style='font-size: large '><br> [" + discount3 + "% discount ] </div>";
        document.getElementById("AllInclusive_discount").style.display = "block";
    } else {
        document.getElementById("AllInclusive_price").innerHTML = "<sup>$</sup>" + price_allinclusive[x];
        document.getElementById("AllInclusive_discount").style.display = "none";
    }

}

var coll = document.getElementsByClassName("collapsible");
var i;

for (i = 0; i < coll.length; i++) {
    coll[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var content = this.nextElementSibling;
        if (content.style.display === "block") {
            content.style.display = "none";
        } else {
            content.style.display = "block";
        }
    });
}
