print_r = function(a, dTab) {
  //initiate the return variable
  var ret = "";

  //the depth tabbing variable helps in indentation
  if(!dTab) dTab = "\t";

  //If the input variable is a collection object then iterate
  if(typeof(a) == 'object'){

    //foreach implementation in javascript
    for(var sub in a) {
      var val = a[sub];
      ret += "'" + sub + "' =>";

      //incase the value obtained is again a collection
      if(typeof(val) == 'object') {

        //drill it down by calling the print_r function recurrsively
        ret += "\n" + dTab + "[" + print_r(val, dTab + "\t") + "]\n" + (dTab.substring(0, (dTab.length-1)));
      } else {
        ret += " \"" + val + "\"";
      }
    }
  } else {
    //Not a collection
    ret = "'" + a + "' is of type '" + typeof(a) + "', not array/object.";
  }
  return ret;
}