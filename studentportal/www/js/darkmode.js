// Load saved theme
const savedTheme = localStorage.getItem("theme") || "light";
document.body.classList.add(savedTheme + "-mode");

// Toggle theme
document.getElementById("toggleMode").addEventListener("click", () => {
  const currentTheme = document.body.classList.contains("dark-mode") ? "dark" : "light";
  const newTheme = currentTheme === "dark" ? "light" : "dark";

  document.body.classList.remove(currentTheme + "-mode");
  document.body.classList.add(newTheme + "-mode");

  localStorage.setItem("theme", newTheme);
});
