function validateRadius(radius) {
const r = 2;
const res = 2 * Math.PI * r;
console.log(`${res.toFixed(3)}`);
  
  // Check if the radius is a number
  if (typeof r !== "number" || isNaN(r)) {
    return false;
  }

  // Check if the radius is positive
  if (radius <= 0) {
    return false;
  }

  return true;
}
