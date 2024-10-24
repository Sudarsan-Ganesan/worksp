/* Palindrome in checker */
function isPalindrome(str) {
    let j = str.length - 1
    for (let i = 0; i < str.length / 2; i++) {
        if (str[i] != str[j]) {
            return false;
        }
        j--;
    }
    return true;
}

let str1 = "civic";
let str2 = "noon";
let str3 = "Rama";

console.log(isPalindrome(str1));
console.log(isPalindrome(str2));
console.log(isPalindrome(str3));


/* Find the biggest in given number */
        function findBiggest(numbers) {
          if (numbers.length === 0) {
            return undefined; // Handle empty array case
          }
        
          let biggest = numbers[0]; // Assume the first number is the biggest
        
          for (let i = 1; i < numbers.length; i++) {
            if (numbers[i] > biggest) {
              biggest = numbers[i];
            }
          }
        
          return biggest;
        }
        
        /* const numbers = [10, 5, 8, 20, 3, 6, 17, 15]; */
        const biggestNumber = findBiggest(numbers);
        
        console.log("The biggest number is:", biggestNumber); // Output: 20
