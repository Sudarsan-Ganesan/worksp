/* Fibonacci */
function printFibonacci(n) {
    let a = 0, b = 1, c;
    console.log(a);
    console.log(b);
    for (let i = 2; i < n; i++) {
      c = a + b;
      console.log(c);
      a = b;
      b = c;
    }
  }
  
  printFibonacci(10);


/* For loop with two variables */  
function num(numbers) {
  let num1 = numbers[0];
  let num2 = numbers[0];
  for (let i = 0; i < num1; i++) {
    for (let j = 0; j < num2; j++) {
      let z = i+j;
      console.log(z);
    }
  }
}

num([5]);
