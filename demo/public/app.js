function DataStuff($scope, $http) {
  function update(){
      $http.get('/').success(function(data) {
        $scope.data = data;
        console.log($scope.data);
      });
      
      setTimeout(update, 10000);
  }
  update();

  $scope.select = function(data){
      console.log(data);
      $scope.selected = data.id;
      //little hack to assign pics to number
  }
}
