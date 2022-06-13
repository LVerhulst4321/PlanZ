/**
 * When page loads, add event handler to each table tent type radio button.
 * When radio button changes, show correct picture and instruction.
 */
window.addEventListener("load", () => {
    document.querySelectorAll(".tent-type").forEach((el) =>
        el.addEventListener("change", (e) => {
            if (e.target.value == "fold-2") {
                document.querySelector(".fold-2").classList.remove("hidden");
                document.querySelector(".fold-3").classList.add("hidden");
            }
            else {
                document.querySelector(".fold-2").classList.add("hidden");
                document.querySelector(".fold-3").classList.remove("hidden");
            }
        })
    );
});
