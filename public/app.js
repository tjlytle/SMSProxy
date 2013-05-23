function DataStuff($scope, $http) {
  function update(){
      $http.get('../data.php').success(function(data) {
        $scope.data = data;
        console.log(data);
      });
      
      setTimeout(update, 5000);
  }
  update();
}
