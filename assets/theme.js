(function () {
    var themes = ["light", "dark", "warm"];
    var labels = { light: "浅色", dark: "深色", warm: "护眼" };

    function current() {
        var t = localStorage.getItem("theme");
        return themes.indexOf(t) === -1 ? "light" : t;
    }

    function apply(t) {
        document.documentElement.setAttribute("data-theme", t);
        var b = document.getElementById("themeToggle");
        if (b) b.textContent = labels[t] || "主题";
    }

    function bind() {
        var b = document.getElementById("themeToggle");
        if (!b) return;
        apply(current());
        b.addEventListener("click", function () {
            var t = current();
            var i = themes.indexOf(t);
            var next = themes[(i + 1) % themes.length];
            localStorage.setItem("theme", next);
            apply(next);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", bind);
    } else {
        bind();
    }
    apply(current());
})();