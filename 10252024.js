const numberArr = [5, 9, 2, 8, 4, 10, 1, 3, 7, 6];

function sortedFunction(arr) {
    for (let i = 0; i < arr.length; i++) {
    for (let j = i + 1; j < arr.length; j++) {
      if (arr[i] > arr[j]) {
        // Swap elements if the current element is greater than the next
        let temp = arr[i];
        arr[i] = arr[j];
        arr[j] = temp;
      }
    }
  }
  return arr;
}
console.log("Sorted Array:", sortedFunction(numberArr));
