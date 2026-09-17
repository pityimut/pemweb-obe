// js/utils.js
export function ringkasInventaris(data) {
    const totalItem = data.length;
    const totalHarga = data.reduce((sum, item) => sum + item.harga, 0);
    
    return {
        totalItem,
        totalHarga
    };
}