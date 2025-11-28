document.addEventListener("DOMContentLoaded", () => {
    let box = document.getElementById("searchBox");
    let output = document.getElementById("searchResults");

    if (box) {
        box.addEventListener("keyup", () => {
            let q = box.value.trim();

            if (q.length < 2) {
                output.innerHTML = "";
                return;
            }

            fetch("ajax/search.php?q=" + q)
                .then(res => res.text())
                .then(data => output.innerHTML = data);
        });
    }
});
