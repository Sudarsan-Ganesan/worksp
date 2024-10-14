const r = 7;

// Function to validate the r (radius)
function validateRadius(r) {
  // Check if the radius is a number
  if (typeof r !== "number" || isNaN(r)) {
    console.log("Invalid input: Radius should be a number.");
    return false;
  }
console.log(r);
  // Check if the r (radius) is positive
  if (r <= 0) {
    console.log("Invalid input: Radius should be a positive number.");
    return false;
  }

  return true;
}

// If valid, calculate the circumference
if (validateRadius(r)) {
  const res = 2 * Math.PI * r;
  console.log(`The circumference is: ${res.toFixed(3)}`);
} else {
  console.log("Radius validation failed.");
}

validateRadius(r);
