(function(o){
	var data = null;
	var itemNums = {};
	o.init = function(comments) {
		data = comments;
		for (var i = data.length - 1; i >= 0; i--) {
			if(!itemNums[data[i].itemIdTop]) itemNums[data[i].itemIdTop]=0;
			itemNums[data[i].itemIdTop]++;
		}
	};
	o.getNum = function(itemId) {
		return itemNums[itemId];
	};
	o.update = function(itemId){
		var commentNum = o.getNum(itemId);
		$("#commentNum").text(commentNum || "");
		var href = $("#commentLink").attr('href');
		$("#commentLink").attr('href','?i='+itemId+'&do=comment');
	};
}(window.Comments = {}));